import csv
from datetime import datetime, date, time, timedelta
import argparse
import pandas as pd

def NGtoMet( ngridfile, metasysfile ):
  df = pd.read_csv( ngridfile )
  df = df.dropna()
  df.sort_values( by=['Date'], inplace=True )
  headings = df.columns.values
  print( headings )

  with open( metasysfile, mode='w', newline="", encoding='utf-8' ) as w:
    csvwriter = csv.writer( w )
    csvwriter.writerow( ['Date / Time', 'Name Path Reference', 'Object Name', 'Object Value'] )

    sum = {}

    for ngridline in df.itertuples():
      if ( ngridline[1] != "" ) and ( ngridline[2] != "" ) and ( ngridline[4] != "" ):
        units = ngridline[4]
        print( ngridline )
        colname = ngridline[1] + '.' + units
        sumname = colname + ".sum"

        for index in range( 4, len( ngridline ) - 1 ):
          cell = ngridline[index+1]

          if cell != "":
            timesplit = headings[index].split( ':' )
            datesplit = ngridline[2].split( '/' )
            dt = datetime( int( datesplit[2] ), int( datesplit[0] ), int( datesplit[1] ) )

            if ( timesplit[0] == '24' ):
              dt += timedelta( days=1 )
              timesplit[0] = '00'

            dt += timedelta( hours=int( timesplit[0] ), minutes=int( timesplit[1] ) )
            timestamp = dt.strftime( '%m/%d/%Y %H:%M' )
            csvwriter.writerow( [ timestamp, '', colname, cell ] )

            if ( ( units == 'kWh' ) or ( units == 'kVAh' ) ):

              if ( sumname not in sum ):
                sum[sumname] = 0

              sum[sumname] += cell
              csvwriter.writerow( [ timestamp, '', sumname, sum[sumname] ] )

  return



if __name__ == "__main__":
  parser = argparse.ArgumentParser( description='converts NG output files to metasys filetype' )
  parser.add_argument( '-i', dest='input_file',  help='name of input file' )
  parser.add_argument( '-o', dest='output_file', help='name of output file' )
  args = parser.parse_args()
  NGtoMet( args.input_file,args.output_file )
